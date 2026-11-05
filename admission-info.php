<?php
/**
 * Sirajganj Road Chowrasta High School - Admission Information
 */

$pageTitle = 'Admission Information - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';

try {
    $feeTypes = Database::fetchAll("SELECT * FROM fee_types WHERE status = 'active' ORDER BY id ASC");
    $classes = Database::fetchAll("SELECT * FROM classes WHERE status = 'active' ORDER BY numeric_value ASC");
} catch (Exception $e) {
    $feeTypes = [];
    $classes = [];
}
$admissionStatus = getSetting('online_admission_status', 'open');
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Admission Information</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Admission Guidelines</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <!-- Status Notification Banner -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-5 text-center text-md-start" style="background-color: #F5F7F5; border-left: 6px solid <?= $admissionStatus === 'open' ? '#198754' : '#dc3545' ?> !important;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge <?= $admissionStatus === 'open' ? 'bg-success' : 'bg-danger' ?> px-3 py-2 rounded-pill fs-6 mb-2">
                        Admission <?= strtoupper($admissionStatus) ?> for Session <?= date('Y') ?>
                    </span>
                    <h4 class="fw-bold text-dark mb-1">
                        <?= $admissionStatus === 'open' ? 'Applications Now Being Accepted for Classes 6 through 9' : 'Admission Window Currently Concluded' ?>
                    </h4>
                    <p class="text-muted small mb-0">
                        Interested guardians may submit online forms through our portal or collect paper applications from the school office.
                    </p>
                </div>
                <?php if ($admissionStatus === 'open'): ?>
                    <a href="<?= url('admission.php') ?>" class="btn btn-warning px-4 py-2 rounded-pill fw-bold text-dark shadow-sm">
                        <i class="fas fa-edit me-1"></i> Apply Online Now &rarr;
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4 Step Process -->
        <div class="mb-5">
            <div class="text-center mb-5">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-list-ol me-1"></i> How It Works</span>
                <h3 class="fw-bold text-dark mt-1" style="color: #123B2A;">Admission Procedure in 4 Easy Steps</h3>
            </div>

            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold mb-3 fs-4" style="width: 60px; height: 60px; background-color: #123B2A;">
                            1
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Fill Application</h6>
                        <small class="text-muted">Complete the digital form online or fill out the physical application at the school office.</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold mb-3 fs-4" style="width: 60px; height: 60px; background-color: #198754;">
                            2
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Submit Documents</h6>
                        <small class="text-muted">Attach birth certificate copy, previous school transfer certificate (TC), and guardian NID.</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold mb-3 fs-4" style="width: 60px; height: 60px; background-color: #D9AD55;">
                            3
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Assessment / Lottery</h6>
                        <small class="text-muted">Attend the basic skill orientation or lottery drawing per Ministry of Education guidelines.</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white fw-bold mb-3 fs-4" style="width: 60px; height: 60px; background-color: #0d6efd;">
                            4
                        </div>
                        <h6 class="fw-bold text-dark mb-2">Final Enrollment</h6>
                        <small class="text-muted">Pay admission fees, receive student roll number, class routine, and complimentary booklist.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Eligibility and Required Documents -->
        <div class="row g-5 mb-5">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                    <h5 class="fw-bold mb-3" style="color: #123B2A;"><i class="fas fa-clipboard-check text-success me-2"></i> Eligibility Criteria</h5>
                    <ul class="list-unstyled text-muted small leading-relaxed">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <div><strong>Class 6 Admission:</strong> Completion of Primary Education / Class 5 PSC with satisfactory conduct certificate.</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <div><strong>Class 7–9 Admission:</strong> Successful promotion from recognized school with official Transfer Certificate (TC) and progress report.</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <div><strong>Age Qualification:</strong> In strict compliance with age limits declared by the Ministry of Education for the active academic session.</div>
                        </li>
                        <li class="mb-0 d-flex align-items-start">
                            <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                            <div><strong>Special Quotas:</strong> Provisions for freedom fighter descendants, disadvantaged students, and sibling concessions as per government rules.</div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white h-100">
                    <h5 class="fw-bold mb-3" style="color: #123B2A;"><i class="fas fa-folder-open text-warning me-2"></i> Documents Required</h5>
                    <ul class="list-unstyled text-muted small leading-relaxed">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-file-alt text-primary me-2 mt-1"></i>
                            <div>Photocopy of Digital Online Birth Registration Certificate (17-digit BRN).</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-file-alt text-primary me-2 mt-1"></i>
                            <div>Original Transfer Certificate (TC) or Primary completion certificate.</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-file-alt text-primary me-2 mt-1"></i>
                            <div>3 copies of recent passport-size photograph (color background).</div>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-file-alt text-primary me-2 mt-1"></i>
                            <div>Photocopy of Father’s and Mother’s National Identity Card (NID).</div>
                        </li>
                        <li class="mb-0 d-flex align-items-start">
                            <i class="fas fa-file-alt text-primary me-2 mt-1"></i>
                            <div>Official blood group laboratory confirmation report.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Official Fee Structure -->
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white border-top border-4 border-success">
            <h5 class="fw-bold mb-4" style="color: #123B2A;"><i class="fas fa-money-check-alt text-success me-2"></i> Standard Approved Fee Structure</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fee Category</th>
                            <th>Description</th>
                            <th>Billing Frequency</th>
                            <th class="text-end">Amount (BDT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($feeTypes)): ?>
                            <?php foreach ($feeTypes as $fee): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= e($fee['fee_name']) ?></td>
                                    <td class="text-muted"><?= e($fee['description'] ?: 'Official institutional fee') ?></td>
                                    <td><span class="badge bg-light text-secondary border text-capitalize"><?= e($fee['frequency']) ?></span></td>
                                    <td class="text-end fw-bold text-success fs-6"><?= formatCurrency($fee['amount']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
