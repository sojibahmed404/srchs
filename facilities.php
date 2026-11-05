<?php
/**
 * Sirajganj Road Chowrasta High School - Campus Facilities
 */

$pageTitle = 'School Facilities - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">School Facilities</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('about.php') ?>" class="text-warning text-decoration-none">About</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Facilities</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Facilities Grid -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="text-success fw-bold text-uppercase small"><i class="fas fa-building me-1"></i> Campus Environment</span>
            <h2 class="display-6 fw-bold text-dark mt-1" style="color: #123B2A;">Designed for Optimal Learning & Well-being</h2>
            <p class="text-muted">Explore the physical, technological, and co-curricular resources supporting our students daily.</p>
        </div>

        <div class="row g-4">
            <!-- Facility 1: Computer Lab -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #123B2A;">
                        <i class="fas fa-laptop-code fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Sheikh Russel Digital Lab</h5>
                    <p class="text-muted small leading-relaxed mb-0">
                        Equipped with 30+ networked desktop computers, high-speed broadband internet, interactive multimedia projectors, and software environments for foundational ICT instruction.
                    </p>
                </div>
            </div>

            <!-- Facility 2: Science Lab -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #198754;">
                        <i class="fas fa-flask fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Science Laboratories</h5>
                    <p class="text-muted small leading-relaxed mb-0">
                        Well-stocked apparatus, chemical reagents, optical microscopes, and safety equipment allowing students in Physics, Chemistry, and Biology to conduct practical hands-on investigations.
                    </p>
                </div>
            </div>

            <!-- Facility 3: Library -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #D9AD55;">
                        <i class="fas fa-book-reader fs-3 text-dark"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Central Library</h5>
                    <p class="text-muted small leading-relaxed mb-0">
                        A peaceful reading sanctuary holding over 5,000 volumes across Bengali literature, world classics, encyclopedia sets, historical archives, and competitive examination reference manuals.
                    </p>
                </div>
            </div>

            <!-- Facility 4: Playground -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #0d6efd;">
                        <i class="fas fa-futbol fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Athletic Grounds & Sports</h5>
                    <p class="text-muted small leading-relaxed mb-0">
                        Expansive natural grass field accommodating football, cricket pitch, badminton and volleyball courts, hosting daily morning assemblies, drill routines, and annual athletic competitions.
                    </p>
                </div>
            </div>

            <!-- Facility 5: Auditorium -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #6f42c1;">
                        <i class="fas fa-theater-masks fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Multipurpose Hall & Stage</h5>
                    <p class="text-muted small leading-relaxed mb-0">
                        Equipped with acoustic sound amplification and stage lighting for hosting guardian conferences, national observance programs, debates, recitation contests, and science exhibitions.
                    </p>
                </div>
            </div>

            <!-- Facility 6: Health & Water -->
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center bg-white hover-shadow transition">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 70px; height: 70px; background-color: #20c997;">
                        <i class="fas fa-hand-holding-water fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Safe Water & Sanitation</h5>
                    <p class="text-muted small leading-relaxed mb-0">
                        Deep tube-well filtered safe drinking water plants, separated hygienic washrooms for boys and girls, hand-washing stations, and dedicated first-aid medical supplies.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?= url('gallery.php') ?>" class="btn btn-outline-success px-4 py-2 rounded-pill me-2">
                <i class="fas fa-images me-1"></i> View Campus Photos
            </a>
            <a href="<?= url('admission.php') ?>" class="btn btn-success px-4 py-2 rounded-pill">
                <i class="fas fa-file-alt me-1"></i> Apply for Admission
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
