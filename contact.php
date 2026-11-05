<?php
/**
 * Sirajganj Road Chowrasta High School - Contact Us
 */

require_once __DIR__ . '/includes/auth.php';

$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$schoolPhone = getSetting('school_phone', '+880 1712-345678');
$schoolEmail = getSetting('school_email', 'info@srchs.edu.bd');
$schoolAddress = getSetting('school_address', 'Hatikumrul, Ullapara, Sirajganj, Bangladesh');
$schoolEIIN = getSetting('school_eiin', '128456');

// Handle Contact Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken()) {
        setFlash('danger', 'Security validation failed (CSRF). Please retry submitting.');
    } else {
        $senderName = getString('name', 'POST');
        $senderEmail = getString('email', 'POST');
        $senderPhone = getString('phone', 'POST');
        $subject = getString('subject', 'POST');
        $message = getString('message', 'POST');

        if (empty($senderName) || empty($senderEmail) || empty($subject) || empty($message)) {
            setFlash('danger', 'Please provide your name, valid email, subject, and message.');
        } elseif (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            setFlash('danger', 'Please enter a valid email address.');
        } else {
            // Log this inquiry in activity logs
            logActivity('contact_message', "Inquiry from {$senderName} ({$senderEmail}, Phone: {$senderPhone}): Subject: {$subject} - Message: " . truncate($message, 120));

            setFlash('success', 'Thank you for contacting Sirajganj Road Chowrasta High School! Your message has been received by our administration office.');
            redirect(url('contact.php'));
        }
    }
}

$pageTitle = 'Contact Us - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Contact Information</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-5">

            <!-- Contact Information Column -->
            <div class="col-lg-5">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-headset me-1"></i> Communication Gateway</span>
                <h2 class="display-6 fw-bold text-dark mt-1 mb-4" style="color: #123B2A;">We Are Here to Assist You</h2>
                <p class="text-muted leading-relaxed mb-4">
                    Have questions about admissions, student records, curricular tracks, or examinations? Visit our campus or reach out via phone or email during administrative office hours.
                </p>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-3" style="background-color: #F5F7F5;">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-3 flex-shrink-0" style="width: 48px; height: 48px; background-color: #123B2A;">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Campus Location</h6>
                            <p class="text-muted small mb-0"><?= e($schoolAddress) ?></p>
                            <small class="text-success fw-semibold">Near Hatikumrul Highway Junction, Ullapara</small>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-3" style="background-color: #F5F7F5;">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-3 flex-shrink-0" style="width: 48px; height: 48px; background-color: #198754;">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Telephone & Mobile</h6>
                            <p class="text-muted small mb-0"><a href="tel:<?= e($schoolPhone) ?>" class="text-muted text-decoration-none"><?= e($schoolPhone) ?></a></p>
                            <small class="text-muted">Office Hours: 09:00 AM - 04:00 PM</small>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background-color: #F5F7F5;">
                    <div class="d-flex align-items-start">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-3 flex-shrink-0" style="width: 48px; height: 48px; background-color: #D9AD55;">
                            <i class="fas fa-envelope text-dark"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Official Email</h6>
                            <p class="text-muted small mb-0"><a href="mailto:<?= e($schoolEmail) ?>" class="text-muted text-decoration-none"><?= e($schoolEmail) ?></a></p>
                            <small class="text-muted">Responses provided within 24 business hours</small>
                        </div>
                    </div>
                </div>

                <!-- Office Hours Card -->
                <div class="card border-0 rounded-4 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center small">
                        <span><i class="fas fa-calendar-alt text-success me-2"></i> <strong>Working Days:</strong></span>
                        <span class="text-muted">Sunday — Thursday</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mt-2">
                        <span><i class="fas fa-clock text-warning me-2"></i> <strong>Academic Timing:</strong></span>
                        <span class="text-muted">09:00 AM — 03:00 PM</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small mt-2">
                        <span><i class="fas fa-ban text-danger me-2"></i> <strong>Weekly Holiday:</strong></span>
                        <span class="badge bg-secondary">Friday & Saturday</span>
                    </div>
                </div>

            </div>

            <!-- Contact Form Column -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white" style="border-top: 6px solid #123B2A !important;">
                    <h4 class="fw-bold mb-2" style="color: #123B2A;">Send Us a Written Message</h4>
                    <p class="text-muted small mb-4">Please fill out the form below and our administrative team will address your query promptly.</p>

                    <form action="<?= url('contact.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Md. Zahid Hasan" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="name@domain.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mobile Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="017xxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Subject Matter <span class="text-danger">*</span></label>
                                <input type="text" name="subject" class="form-control" placeholder="e.g. Admission Inquiry / Marksheet verification" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Your Detailed Inquiry <span class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="5" placeholder="Write your message or inquiry here..." required></textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-success px-5 py-2 rounded-pill fw-bold">
                                    <i class="fas fa-paper-plane me-1"></i> Send Official Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
