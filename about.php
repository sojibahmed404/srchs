<?php
/**
 * Sirajganj Road Chowrasta High School - About Page
 */

$pageTitle = 'About Our School - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';

$schoolName = getSetting('school_name', 'Sirajganj Road Chowrasta High School');
$schoolEstd = getSetting('school_estd', '1974');
$schoolEIIN = getSetting('school_eiin', '128456');
$schoolBoard = getSetting('school_board', 'Rajshahi Education Board');
$headmasterName = getSetting('headmaster_name', 'Md. Rafiqul Islam');
$headmasterQuote = getSetting('headmaster_quote', 'Empowering youth with intellectual rigor, moral integrity, and technological fluency.');
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">About Our School</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Overview Section -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-landmark me-1"></i> Institution Overview</span>
                <h2 class="display-6 fw-bold text-dark mt-1 mb-4" style="color: #123B2A;">
                    A Half-Century Legacy of Educational Excellence in Sirajganj
                </h2>
                <p class="text-muted leading-relaxed mb-3">
                    Sirajganj Road Chowrasta High School (SRCHS) is a prominent secondary school located at Hatikumrul, the vibrant crossroads connecting northern Bangladesh with Dhaka and the national highway network. Founded in 1974 through the visionary philanthropy of local educators, community leaders, and guardians, our school has grown from humble beginnings into a celebrated center of academic enlightenment.
                </p>
                <p class="text-muted leading-relaxed mb-4">
                    The school caters to students from Class 6 to Class 10, preparing them rigorously for the Secondary School Certificate (SSC) examination under the Rajshahi Education Board. Our educational framework synthesizes national curriculum objectives with modern instructional technology, character formation, and athletic vigor.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= url('history.php') ?>" class="btn btn-outline-success px-4 py-2 rounded-pill fw-semibold">
                        <i class="fas fa-history me-1"></i> Read School History
                    </a>
                    <a href="<?= url('headmaster-message.php') ?>" class="btn btn-outline-dark px-4 py-2 rounded-pill fw-semibold">
                        <i class="fas fa-user-tie me-1"></i> Headmaster's Message
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4" style="background-color: #F5F7F5;">
                    <h5 class="fw-bold mb-3" style="color: #123B2A;"><i class="fas fa-info-circle text-success me-2"></i> Institutional Facts</h5>
                    <ul class="list-group list-group-flush bg-transparent">
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Full Name</span>
                            <span class="fw-bold text-end"><?= e($schoolName) ?></span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">EIIN</span>
                            <span class="badge bg-success fs-6"><?= e($schoolEIIN) ?></span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Established</span>
                            <span class="fw-bold"><?= e($schoolEstd) ?> (52+ Years)</span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Affiliated Board</span>
                            <span class="fw-bold"><?= e($schoolBoard) ?></span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Level</span>
                            <span class="fw-bold">Secondary (Class 6–10)</span>
                        </li>
                        <li class="list-group-item bg-transparent d-flex justify-content-between px-0 py-2">
                            <span class="text-muted">Campus Location</span>
                            <span class="fw-bold">Hatikumrul, Ullapara</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision Section -->
<section class="py-5" style="background-color: #F5F7F5;">
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white mb-4" style="width: 60px; height: 60px; background-color: #123B2A;">
                        <i class="fas fa-bullseye fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-3">Our Mission</h4>
                    <p class="text-muted leading-relaxed mb-0">
                        To provide an inclusive, stimulating, and disciplined academic environment where every student develops a deep love for learning, critical analytical thinking, ethical sensibility, and the essential 21st-century digital competencies required to excel in higher education and contribute meaningfully to the progress of Bangladesh.
                    </p>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white mb-4" style="width: 60px; height: 60px; background-color: #198754;">
                        <i class="fas fa-eye fs-3"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-3">Our Vision</h4>
                    <p class="text-muted leading-relaxed mb-0">
                        To stand as a leading model secondary institution in the Rajshahi Division, recognized for academic distinction, scientific inquiry, moral fortitude, and technological empowerment, shaping students into compassionate global citizens and proud architects of a modern, prosperous nation.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Core Values Section -->
<section class="py-5 bg-white">
    <div class="container py-4 text-center">
        <span class="text-success fw-bold text-uppercase small"><i class="fas fa-heart me-1"></i> Core Principles</span>
        <h2 class="h3 fw-bold text-dark mt-1 mb-5" style="color: #123B2A;">Our Foundational Values</h2>

        <div class="row g-4">
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-warning bg-light mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-award fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Excellence</h6>
                    <small class="text-muted">Pursuing continuous improvement in academics and personal growth.</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-success bg-light mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-shield-alt fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Integrity</h6>
                    <small class="text-muted">Upholding truth, honesty, and moral accountability in all actions.</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-primary bg-light mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-users fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Inclusivity</h6>
                    <small class="text-muted">Fostering mutual respect, empathy, and equal care for every student.</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-info bg-light mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-lightbulb fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2">Innovation</h6>
                    <small class="text-muted">Embracing digital technology and creative inquiry in education.</small>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
