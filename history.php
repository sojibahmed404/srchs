<?php
/**
 * Sirajganj Road Chowrasta High School - School History
 */

$pageTitle = 'School History - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">School History</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('about.php') ?>" class="text-warning text-decoration-none">About</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">History</li>
            </ol>
        </nav>
    </div>
</section>

<!-- History Content & Timeline -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-landmark me-1"></i> Genesis & Milestones</span>
                <h2 class="display-6 fw-bold text-dark mt-1 mb-4" style="color: #123B2A;">
                    Five Decades of Dedication to Knowledge (1974 - Present)
                </h2>
                <p class="text-muted leading-relaxed">
                    Following the independence of Bangladesh in 1971, local community leaders, philanthropists, and passionate educators of the Hatikumrul-Ullapara region recognized that sustainable national rejuvenation required widespread access to quality secondary education.
                </p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Timeline Item 1 -->
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5" style="background-color: #F5F7F5; border-left: 5px solid #123B2A !important;">
                    <div class="badge bg-success px-3 py-2 rounded-pill fs-6 mb-3 align-self-start">1974</div>
                    <h4 class="fw-bold text-dark mb-3">The Inception & First Classroom</h4>
                    <p class="text-muted leading-relaxed mb-0">
                        In early 1974, through land donations and cooperative local volunteer efforts, Sirajganj Road Chowrasta High School was officially inaugurated. The school started with a dedicated faculty of six visionary teachers offering lower secondary classes in temporary semi-pucca classrooms.
                    </p>
                </div>
            </div>

            <!-- Timeline Item 2 -->
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5" style="background-color: #F5F7F5; border-left: 5px solid #198754 !important;">
                    <div class="badge bg-primary px-3 py-2 rounded-pill fs-6 mb-3 align-self-start">1982</div>
                    <h4 class="fw-bold text-dark mb-3">Permanent Campus & Board Recognition</h4>
                    <p class="text-muted leading-relaxed mb-0">
                        The school received official recognition from the Board of Intermediate and Secondary Education, Rajshahi, as a full-fledged secondary school. Construction of the initial two-story main administrative and academic building was completed with community and government contributions.
                    </p>
                </div>
            </div>

            <!-- Timeline Item 3 -->
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5" style="background-color: #F5F7F5; border-left: 5px solid #D9AD55 !important;">
                    <div class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6 mb-3 align-self-start">2005</div>
                    <h4 class="fw-bold text-dark mb-3">Science Laboratory & Sports Modernization</h4>
                    <p class="text-muted leading-relaxed mb-0">
                        SRCHS established comprehensive laboratory facilities for physics, chemistry, and biology experiments. The school athletic field was expanded to accommodate inter-school football, volleyball, and track events, producing numerous district-level championship victories.
                    </p>
                </div>
            </div>

            <!-- Timeline Item 4 -->
            <div class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 p-md-5" style="background-color: #F5F7F5; border-left: 5px solid #0d6efd !important;">
                    <div class="badge bg-info text-white px-3 py-2 rounded-pill fs-6 mb-3 align-self-start">2018 - 2026</div>
                    <h4 class="fw-bold text-dark mb-3">Digital Transformation & Modern Portal</h4>
                    <p class="text-muted leading-relaxed mb-0">
                        Equipped with a modern Sheikh Russel Digital ICT Lab and high-speed multimedia classrooms, SRCHS launched this integrated web portal and school management platform to ensure seamless online admission, automated marksheet calculation, transparent attendance, and global accessibility.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?= url('about.php') ?>" class="btn btn-outline-success px-4 py-2 rounded-pill">
                &larr; Back to About Us
            </a>
            <a href="<?= url('teachers.php') ?>" class="btn btn-success px-4 py-2 rounded-pill ms-2">
                Meet Current Faculty &rarr;
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
