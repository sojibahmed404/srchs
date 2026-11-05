    <!-- ========== Footer ========== -->
    <footer class="pt-5 pb-0 mt-5" style="background-color: #0a1f14; color: #ffffff;">
        <div class="container">
            <div class="row g-4 pb-4">

                <!-- Column 1: About -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="fw-bold mb-3" style="color: #D9AD55; font-family: 'Hind Siliguri', sans-serif;">
                        <?= e($schoolName) ?>
                    </h5>
                    <p class="text-white-50 small mb-2">
                        Sirajganj Road Chowrasta High School is a renowned educational institution committed to
                        academic excellence and holistic development. Established with a vision to nurture future
                        leaders, we provide quality education rooted in values and modern pedagogy.
                    </p>
                    <p class="text-white-50 small mb-0">
                        <strong class="text-white">EIIN:</strong> <?= e(getSetting('school_eiin', '126789')) ?>
                    </p>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="fw-bold mb-3 text-white">Quick Links</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <a href="<?= url('index.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('about.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> About
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('teachers.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Teachers
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('notices.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Notice Board
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('results.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Results
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('admission.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Admission
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('gallery.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Gallery
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= url('contact.php') ?>" class="text-white-50 text-decoration-none link-hover">
                                <i class="fas fa-chevron-right me-1 small"></i> Contact
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 3: Contact Info -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3 text-white">Contact Us</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-map-marker-alt me-2 mt-1" style="color: #D9AD55;"></i>
                            <span class="text-white-50"><?= e($schoolAddress) ?></span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-phone-alt me-2 mt-1" style="color: #D9AD55;"></i>
                            <a href="tel:<?= e($schoolPhone) ?>" class="text-white-50 text-decoration-none">
                                <?= e($schoolPhone) ?>
                            </a>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fas fa-envelope me-2 mt-1" style="color: #D9AD55;"></i>
                            <a href="mailto:<?= e($schoolEmail) ?>" class="text-white-50 text-decoration-none">
                                <?= e($schoolEmail) ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Column 4: Connect -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold mb-3 text-white">Follow Us</h6>
                    <div class="d-flex gap-3 mb-3">
                        <a href="<?= e(getSetting('facebook_url', '#')) ?>" target="_blank" rel="noopener noreferrer"
                           class="d-flex align-items-center justify-content-center rounded-circle text-white text-decoration-none"
                           style="width: 40px; height: 40px; background-color: #198754;" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="<?= e(getSetting('youtube_url', '#')) ?>" target="_blank" rel="noopener noreferrer"
                           class="d-flex align-items-center justify-content-center rounded-circle text-white text-decoration-none"
                           style="width: 40px; height: 40px; background-color: #198754;" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                    <p class="text-white-50 small mt-4 mb-0">
                        <i class="fas fa-code me-1"></i> Powered by PHP &amp; MySQL
                    </p>
                </div>

            </div>
        </div>

        <!-- Footer Bottom Bar -->
        <div class="border-top" style="border-color: rgba(255,255,255,0.1) !important; background-color: #071a10;">
            <div class="container">
                <div class="row py-3 align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0 small text-white-50">
                            &copy; <?= date('Y') ?> <?= e($schoolName) ?>. All rights reserved.
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="mb-0 small text-white-50">
                            Developed with <span style="color: #e25555;">&#10084;</span> for Education
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- ========== Scripts ========== -->
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (loaded on dashboard pages only) -->
    <?php if (isset($loadChartJs) && $loadChartJs): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <!-- Custom JS -->
    <script src="<?= asset('assets/js/main.js') ?>"></script>
    <?php if (isset($extraJs)): ?>
    <?= $extraJs ?>
    <?php endif; ?>
</body>
</html>
