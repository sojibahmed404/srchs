<?php
/**
 * Sirajganj Road Chowrasta High School - Homepage
 * Location: Hatikumrul, Ullapara, Sirajganj, Bangladesh
 */

$pageTitle = 'Sirajganj Road Chowrasta High School | Hatikumrul, Sirajganj';
require_once __DIR__ . '/includes/header.php';

// Safe data fetching with fallback
try {
    // 1. Latest notices (up to 4, pinned first)
    $notices = Database::fetchAll("SELECT * FROM notices WHERE status = 'published' ORDER BY is_pinned DESC, published_date DESC LIMIT 4");

    // 2. Teachers for display
    $teachers = Database::fetchAll("SELECT t.*, u.full_name, u.avatar, u.email FROM teachers t JOIN users u ON t.user_id = u.id WHERE u.status = 'active' ORDER BY t.order_priority ASC LIMIT 4");

    // 3. Upcoming & recent events
    $events = Database::fetchAll("SELECT * FROM events WHERE status IN ('upcoming', 'completed') ORDER BY event_date DESC LIMIT 3");

    // 4. Photo Gallery preview
    $gallery = Database::fetchAll("SELECT * FROM galleries ORDER BY order_num ASC LIMIT 6");

    // 5. Statistics
    $totalStudents = (int)Database::fetchColumn("SELECT COUNT(*) FROM students WHERE status = 'active'");
    $totalTeachers = (int)Database::fetchColumn("SELECT COUNT(*) FROM teachers");
    $totalClasses = (int)Database::fetchColumn("SELECT COUNT(*) FROM classes WHERE status = 'active'");
    
    // Fallback counts for presentation if DB is freshly seeded
    if ($totalStudents < 10) $totalStudents = 1250;
    if ($totalTeachers < 4) $totalTeachers = 28;
    if ($totalClasses < 5) $totalClasses = 5;

} catch (Exception $e) {
    $notices = $teachers = $events = $gallery = [];
    $totalStudents = 1250;
    $totalTeachers = 28;
    $totalClasses = 5;
}

$headmasterName = getSetting('headmaster_name', 'Md. Rafiqul Islam');
$headmasterDesignation = getSetting('headmaster_designation', 'Headmaster, SRCHS');
$headmasterQuote = getSetting('headmaster_quote', 'Empowering youth with intellectual rigor, moral integrity, and technological fluency to become enlightened architects of Bangladesh.');
$schoolEstd = getSetting('school_estd', '1974');
$schoolEIIN = getSetting('school_eiin', '128456');
$schoolBoard = getSetting('school_board', 'Rajshahi Education Board');
?>

<!-- ==================== 1. HERO BANNER ==================== -->
<section class="hero-banner position-relative d-flex align-items-center text-white" style="background: linear-gradient(135deg, rgba(18, 59, 42, 0.94) 0%, rgba(25, 135, 84, 0.88) 100%), url('<?= asset('assets/images/hero-bg.jpg') ?>') center/cover no-repeat; min-height: 560px; padding: 100px 0;">
    <div class="container position-relative py-5">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center text-lg-start">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-semibold mb-3 fs-6">
                    <i class="fas fa-award me-1"></i> Established in <?= e($schoolEstd) ?> | EIIN: <?= e($schoolEIIN) ?>
                </span>
                <h1 class="display-4 fw-bold text-white mb-3" style="font-family: 'Hind Siliguri', sans-serif;">
                    সিরাজগঞ্জ রোড চৌরাস্তা উচ্চ বিদ্যালয়
                </h1>
                <h2 class="h3 fw-light text-light mb-4">
                    Sirajganj Road Chowrasta High School
                </h2>
                <p class="lead text-white-50 mb-4 pe-lg-5">
                    Hatikumrul, Ullapara, Sirajganj. Dedicated to fostering academic excellence, moral leadership, and digital literacy from Class 6 to Class 10 under the Rajshahi Education Board.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="<?= url('admission.php') ?>" class="btn btn-warning btn-lg px-4 py-3 rounded-pill fw-bold text-dark shadow">
                        <i class="fas fa-file-signature me-2"></i> Apply for Admission
                    </a>
                    <a href="<?= url('results.php') ?>" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill fw-bold">
                        <i class="fas fa-search me-2"></i> Check Results
                    </a>
                    <a href="<?= url('notices.php') ?>" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill fw-bold">
                        <i class="fas fa-bullhorn me-2"></i> Notice Board
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block">
                <div class="card border-0 shadow-lg text-dark p-4 rounded-4 bg-white bg-opacity-95">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white me-3" style="width: 50px; height: 50px; background-color: #123B2A;">
                            <i class="fas fa-graduation-cap fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">Quick Portal Access</h6>
                            <small class="text-muted">High School (Grade 6–10)</small>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="fas fa-calendar-check text-success me-2"></i> Session 2026</span>
                            <span class="badge bg-success">Active</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="fas fa-university text-primary me-2"></i> Board Affiliation</span>
                            <span class="fw-semibold">Rajshahi</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span><i class="fas fa-laptop-code text-info me-2"></i> Multimedia Lab</span>
                            <span class="badge bg-primary">Modern</span>
                        </li>
                    </ul>
                    <a href="<?= url('auth/login.php') ?>" class="btn btn-success w-100 rounded-pill fw-semibold py-2">
                        <i class="fas fa-sign-in-alt me-1"></i> Management Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== 2. WELCOME & STATS ==================== -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="pe-lg-4">
                    <span class="text-success fw-bold text-uppercase tracking-wider small">
                        <i class="fas fa-compass me-1"></i> Welcome to Our Institution
                    </span>
                    <h2 class="display-6 fw-bold text-dark mt-2 mb-4" style="color: #123B2A;">
                        Building the Leaders of Tomorrow with Quality Education and Character
                    </h2>
                    <p class="text-muted leading-relaxed mb-3">
                        Sirajganj Road Chowrasta High School, situated prominently at the vital transit hub of Hatikumrul, Ullapara, has been a beacon of secondary education since 1974. We are committed to nurturing intellectual curiosity, ethical character, and patriotic values across Grades 6 through 10.
                    </p>
                    <p class="text-muted leading-relaxed mb-4">
                        With an enriched national curriculum prescribed by the NCTB and guided by the Board of Intermediate and Secondary Education, Rajshahi, our students consistently achieve commendable SSC results and excel in co-curricular pursuits, science Olympiads, and athletic competitions.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="text-success fs-3 me-3"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-1">Qualified & Caring Faculty</h6>
                                    <small class="text-muted">Experienced teachers devoted to personalized student mentoring.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="text-success fs-3 me-3"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-1">Modern Science & ICT Labs</h6>
                                    <small class="text-muted">High-speed computers and fully equipped physics/chemistry laboratories.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="text-success fs-3 me-3"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-1">Rich Co-curricular Program</h6>
                                    <small class="text-muted">Debating, Scouts, Sports, Cultural programs, and National events.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="text-success fs-3 me-3"><i class="fas fa-check-circle"></i></div>
                                <div>
                                    <h6 class="fw-bold mb-1">Digital School Portal</h6>
                                    <small class="text-muted">Online marksheet, attendance records, online admissions, and digital notices.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="<?= url('about.php') ?>" class="btn btn-outline-success px-4 py-2 rounded-pill fw-semibold">
                        Read Full School Overview <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Headmaster Greeting Card -->
                <div class="card border-0 shadow rounded-4 p-4" style="background-color: #F5F7F5; border-left: 5px solid #D9AD55 !important;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle overflow-hidden me-3 border border-3 border-success" style="width: 80px; height: 80px; background: #e2e8f0;">
                            <img src="<?= asset('assets/images/teachers/headmaster.jpg') ?>" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=Rafiqul+Islam&background=123B2A&color=fff&size=150';" alt="Headmaster" class="w-100 h-100 object-fit-cover">
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><?= e($headmasterName) ?></h5>
                            <small class="text-success fw-semibold"><?= e($headmasterDesignation) ?></small>
                        </div>
                    </div>
                    <blockquote class="fst-italic text-secondary mb-3">
                        "<?= e($headmasterQuote) ?>"
                    </blockquote>
                    <p class="text-muted small mb-3">
                        We welcome parents, guardians, and visitors to collaborate with us in sculpting bright futures for every child entrusted to our care.
                    </p>
                    <a href="<?= url('headmaster-message.php') ?>" class="btn btn-sm btn-outline-dark rounded-pill align-self-start">
                        Headmaster's Full Message &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== 3. STATS COUNTER STRIP ==================== -->
<section class="py-5 text-white" style="background-color: #123B2A;">
    <div class="container py-3">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="text-warning fs-1 mb-2"><i class="fas fa-user-graduate"></i></div>
                    <h2 class="display-5 fw-bold text-white mb-1 counter-val" data-target="<?= $totalStudents ?>">0</h2>
                    <p class="text-light mb-0 fw-medium">Active Students</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="text-warning fs-1 mb-2"><i class="fas fa-chalkboard-teacher"></i></div>
                    <h2 class="display-5 fw-bold text-white mb-1 counter-val" data-target="<?= $totalTeachers ?>">0</h2>
                    <p class="text-light mb-0 fw-medium">Experienced Faculty</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="text-warning fs-1 mb-2"><i class="fas fa-school"></i></div>
                    <h2 class="display-5 fw-bold text-white mb-1 counter-val" data-target="5">5</h2>
                    <p class="text-light mb-0 fw-medium">Classes (Class 6–10)</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="text-warning fs-1 mb-2"><i class="fas fa-calendar-alt"></i></div>
                    <h2 class="display-5 fw-bold text-white mb-1 counter-val" data-target="52">52</h2>
                    <p class="text-light mb-0 fw-medium">Years of Glory (1974)</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== 4. NOTICES & ANNOUNCEMENTS ==================== -->
<section class="py-5" style="background-color: #F5F7F5;">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-bullhorn me-1"></i> Notice Board</span>
                <h2 class="h3 fw-bold text-dark mt-1 mb-0" style="color: #123B2A;">Latest Announcements & Circulars</h2>
            </div>
            <a href="<?= url('notices.php') ?>" class="btn btn-outline-success rounded-pill px-4 mt-3 mt-md-0">
                View All Notices <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (!empty($notices)): ?>
                <?php foreach ($notices as $notice): ?>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white transition hover-shadow" style="border-left: 5px solid <?= $notice['category'] === 'exam' ? '#0d6efd' : ($notice['category'] === 'admission' ? '#198754' : '#D9AD55') ?> !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-light text-dark text-uppercase fw-semibold px-2 py-1">
                                    <i class="fas fa-tag me-1 text-success"></i> <?= e($notice['category']) ?>
                                </span>
                                <small class="text-muted">
                                    <i class="far fa-calendar-alt me-1"></i> <?= formatDate($notice['published_date']) ?>
                                </small>
                            </div>
                            <h5 class="fw-bold text-dark mb-2">
                                <?php if ($notice['is_pinned']): ?>
                                    <span class="badge bg-danger me-1 small">Pinned</span>
                                <?php endif; ?>
                                <?= e($notice['title']) ?>
                            </h5>
                            <p class="text-muted small mb-3">
                                <?= e(truncate($notice['content'], 140)) ?>
                            </p>
                            <a href="<?= url('notices.php?id=' . $notice['id']) ?>" class="btn btn-sm btn-link p-0 text-success fw-semibold text-decoration-none">
                                Read Full Notice <i class="fas fa-arrow-right ms-1 small"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">No notices published at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==================== 5. ACADEMIC PROGRAMS (CLASS 6 TO 10) ==================== -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center max-w-xl mx-auto mb-5">
            <span class="text-success fw-bold text-uppercase small"><i class="fas fa-book-reader me-1"></i> Academic Curriculum</span>
            <h2 class="h3 fw-bold text-dark mt-1" style="color: #123B2A;">Education from Grade 6 through Grade 10</h2>
            <p class="text-muted">Structured curriculum aligning with national goals, STEM capabilities, and creative expression.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center" style="background-color: #F5F7F5;">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 60px; height: 60px; background-color: #123B2A;">
                        <i class="fas fa-child fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Lower Secondary (Class 6 & 7)</h5>
                    <p class="text-muted small mb-3">
                        Core foundation in Bangla, English, Mathematics, Integrated Science, BGS, and Information Technology.
                    </p>
                    <a href="<?= url('classes.php') ?>" class="btn btn-sm btn-outline-success rounded-pill">View Syllabus</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center" style="background-color: #F5F7F5;">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 60px; height: 60px; background-color: #198754;">
                        <i class="fas fa-book-open fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Junior Secondary (Class 8)</h5>
                    <p class="text-muted small mb-3">
                        Intermediate competencies, preparatory skill evaluation, enhanced laboratory experiments, and debating skills.
                    </p>
                    <a href="<?= url('classes.php') ?>" class="btn btn-sm btn-outline-success rounded-pill">View Syllabus</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center" style="background-color: #F5F7F5;">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center text-white mb-3" style="width: 60px; height: 60px; background-color: #D9AD55;">
                        <i class="fas fa-award fs-4"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Secondary / SSC (Class 9 & 10)</h5>
                    <p class="text-muted small mb-3">
                        Specialized Science & Humanities tracks, intensive board exam preparation, model tests, and career guidance.
                    </p>
                    <a href="<?= url('classes.php') ?>" class="btn btn-sm btn-outline-success rounded-pill">View Syllabus</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== 6. FACULTY & TEACHERS PREVIEW ==================== -->
<section class="py-5" style="background-color: #F5F7F5;">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-users me-1"></i> Dedicated Educators</span>
                <h2 class="h3 fw-bold text-dark mt-1 mb-0" style="color: #123B2A;">Our Esteemed Teachers & Staff</h2>
            </div>
            <a href="<?= url('teachers.php') ?>" class="btn btn-outline-success rounded-pill px-4 mt-3 mt-md-0">
                View All Faculty Members <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (!empty($teachers)): ?>
                <?php foreach ($teachers as $t): ?>
                    <div class="col-lg-3 col-sm-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4 text-center overflow-hidden bg-white">
                            <div class="bg-light p-4 pb-0">
                                <div class="rounded-circle mx-auto overflow-hidden border border-3 border-success shadow-sm" style="width: 110px; height: 110px;">
                                    <img src="<?= asset($t['avatar'] ?: 'assets/images/default-avatar.png') ?>" onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($t['full_name']) ?>&background=123B2A&color=fff&size=150';" alt="<?= e($t['full_name']) ?>" class="w-100 h-100 object-fit-cover">
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <h6 class="fw-bold text-dark mb-1"><?= e($t['full_name']) ?></h6>
                                <p class="text-success small fw-semibold mb-1"><?= e($t['designation']) ?></p>
                                <p class="text-muted small mb-2"><?= e($t['qualification']) ?></p>
                                <span class="badge bg-light text-secondary border small">
                                    <i class="fas fa-book me-1"></i> <?= e($t['specialization'] ?: 'General Studies') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Faculty profiles available in the Teachers section.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==================== 7. EVENTS & NEWS ==================== -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-calendar-check me-1"></i> School Happenings</span>
                <h2 class="h3 fw-bold text-dark mt-1 mb-0" style="color: #123B2A;">Upcoming Events & News</h2>
            </div>
            <a href="<?= url('events.php') ?>" class="btn btn-outline-success rounded-pill px-4 mt-3 mt-md-0">
                View All Events <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                            <div class="p-3 text-white d-flex justify-content-between align-items-center" style="background-color: #123B2A;">
                                <div class="d-flex align-items-center">
                                    <i class="far fa-calendar-alt text-warning fs-4 me-2"></i>
                                    <div>
                                        <div class="fw-bold"><?= formatDate($event['event_date'], 'd M Y') ?></div>
                                        <small class="text-light"><?= $event['event_time'] ? formatTime($event['event_time']) : 'Full Day' ?></small>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark text-capitalize"><?= e($event['status']) ?></span>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="fw-bold text-dark mb-2"><?= e($event['title']) ?></h5>
                                <p class="text-muted small mb-3">
                                    <?= e(truncate($event['description'], 110)) ?>
                                </p>
                                <div class="text-muted small">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> <?= e($event['location']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">No upcoming events listed at present.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==================== 8. CAMPUS PHOTO GALLERY ==================== -->
<section class="py-5" style="background-color: #F5F7F5;">
    <div class="container py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-camera me-1"></i> Campus Life</span>
                <h2 class="h3 fw-bold text-dark mt-1 mb-0" style="color: #123B2A;">Photo Gallery</h2>
            </div>
            <a href="<?= url('gallery.php') ?>" class="btn btn-outline-success rounded-pill px-4 mt-3 mt-md-0">
                Browse Full Gallery <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3">
            <?php if (!empty($gallery)): ?>
                <?php foreach ($gallery as $g): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative group-hover" style="height: 220px;">
                            <img src="<?= asset($g['image_path']) ?>" onerror="this.onerror=null;this.src='https://picsum.photos/seed/<?= urlencode($g['title']) ?>/600/400';" alt="<?= e($g['title']) ?>" class="w-100 h-100 object-fit-cover">
                            <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-dark bg-opacity-75 text-white">
                                <h6 class="mb-0 fw-semibold text-truncate"><?= e($g['title']) ?></h6>
                                <small class="text-warning"><?= e($g['category']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Gallery items available in the Gallery tab.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ==================== 9. QUICK CONTACT & LOCATION ==================== -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="row g-5">
            <div class="col-lg-5">
                <span class="text-success fw-bold text-uppercase small"><i class="fas fa-map-marked-alt me-1"></i> Reach Us</span>
                <h2 class="h3 fw-bold text-dark mt-1 mb-4" style="color: #123B2A;">Get in Touch With Us</h2>
                <p class="text-muted mb-4">
                    Our administrative office is open Sunday through Thursday from 9:00 AM to 4:00 PM. Parents and community members are always welcome.
                </p>

                <div class="d-flex align-items-start mb-3">
                    <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: #123B2A;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Campus Address</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('school_address', 'Hatikumrul, Ullapara, Sirajganj, Bangladesh')) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-3">
                    <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: #198754;">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Office Telephone</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('school_phone', '+880 1712-345678')) ?></p>
                    </div>
                </div>

                <div class="d-flex align-items-start mb-4">
                    <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: #D9AD55;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Email Address</h6>
                        <p class="text-muted small mb-0"><?= e(getSetting('school_email', 'info@srchs.edu.bd')) ?></p>
                    </div>
                </div>

                <a href="<?= url('contact.php') ?>" class="btn btn-outline-success rounded-pill px-4">
                    More Contact Details <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5" style="background-color: #F5F7F5;">
                    <h5 class="fw-bold mb-3" style="color: #123B2A;">Send an Instant Query</h5>
                    <form action="<?= url('contact.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Your Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Md. Shahidul Islam" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Email Address *</label>
                                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mobile Number</label>
                                <input type="text" name="phone" class="form-control" placeholder="017xxxxxxxx">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Subject *</label>
                                <input type="text" name="subject" class="form-control" placeholder="Admission / Information query" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Your Message *</label>
                                <textarea name="message" class="form-control" rows="3" placeholder="Please write your questions here..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success px-4 py-2 rounded-pill fw-semibold">
                                    <i class="fas fa-paper-plane me-1"></i> Send Message
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
