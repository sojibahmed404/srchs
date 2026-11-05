<?php
/**
 * Sirajganj Road Chowrasta High School - Headmaster's Message
 */

$pageTitle = "Headmaster's Message - Sirajganj Road Chowrasta High School";
require_once __DIR__ . '/includes/header.php';

$headmasterName = getSetting('headmaster_name', 'Md. Rafiqul Islam');
$headmasterDesignation = getSetting('headmaster_designation', 'Headmaster, Sirajganj Road Chowrasta High School');
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Headmaster's Message</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= url('about.php') ?>" class="text-warning text-decoration-none">About</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Headmaster's Message</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-5 align-items-start">
            <div class="col-lg-4 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center sticky-top" style="top: 100px; background-color: #F5F7F5;">
                    <div class="rounded-circle mx-auto overflow-hidden border border-4 border-success shadow mb-3" style="width: 180px; height: 180px;">
                        <img src="<?= asset('assets/images/teachers/headmaster.jpg') ?>" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Rafiqul+Islam&background=123B2A&color=fff&size=200';" alt="Headmaster" class="w-100 h-100 object-fit-cover">
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= e($headmasterName) ?></h5>
                    <p class="text-success fw-semibold small mb-2"><?= e($headmasterDesignation) ?></p>
                    <p class="text-muted small mb-3">M.A. (English), B.Ed. (1st Class)</p>
                    <hr>
                    <div class="text-start small text-muted">
                        <p class="mb-1"><i class="fas fa-calendar-check text-success me-2"></i> Serving Since: 2005</p>
                        <p class="mb-1"><i class="fas fa-envelope text-success me-2"></i> headmaster@srchs.edu.bd</p>
                        <p class="mb-0"><i class="fas fa-phone-alt text-success me-2"></i> +880 1712-345671</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-quote-left me-1"></i> From the Head of Institution</span>
                <h2 class="display-6 fw-bold text-dark mt-1 mb-4" style="color: #123B2A;">
                    Guiding Every Student Toward Wisdom, Humanity, and Civic Pride
                </h2>

                <div class="border-start border-4 border-warning ps-4 mb-4">
                    <p class="lead text-dark fst-italic mb-0">
                        "Education is not merely the accumulation of facts or obtaining certificates; it is the ignition of innate human dignity, the training of character, and the moral compass with which our children shall steer society."
                    </p>
                </div>

                <p class="text-muted leading-relaxed mb-3">
                    Dear Students, Respected Guardians, and Well-Wishers of Sirajganj Road Chowrasta High School,
                </p>
                <p class="text-muted leading-relaxed mb-3">
                    Assalamu Alaikum and warm greetings. It is my profound honor and privilege to welcome you to the official portal of Sirajganj Road Chowrasta High School. Situated at the historic crossroads of Hatikumrul, Ullapara, our school has remained steadfast in its sacred mission since 1974: to enlighten every child, regardless of socio-economic background, with the transformative power of quality secondary education.
                </p>
                <p class="text-muted leading-relaxed mb-3">
                    As we navigate the demands of a rapid 21st century, our curriculum and extracurricular activities are intentionally calibrated. We emphasize not only academic performance in Bengali, English, Mathematics, and Sciences, but also critical thinking, environmental stewardship, artistic expression, and moral responsibility.
                </p>
                <p class="text-muted leading-relaxed mb-3">
                    Our dedicated team of educators approaches teaching as a noble calling. Through regular continuous assessment, digital classroom presentations, laboratory experiments, and close guardian counseling, we endeavor to identify each student’s unique potential and nurture it toward fruition.
                </p>
                <p class="text-muted leading-relaxed mb-4">
                    I express my heartfelt gratitude to the Managing Committee, our vigilant guardians, alumni, and local community leaders whose unwavering support sustains our progress. I invite you to explore this digital portal, follow our academic milestones, and join hands with us in shaping enlightened citizens for Bangladesh.
                </p>

                <div class="pt-3 border-top">
                    <h6 class="fw-bold mb-0 text-dark"><?= e($headmasterName) ?></h6>
                    <small class="text-muted"><?= e($headmasterDesignation) ?></small>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
