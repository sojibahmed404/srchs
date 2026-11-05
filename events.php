<?php
/**
 * Sirajganj Road Chowrasta High School - Events & News
 */

require_once __DIR__ . '/includes/auth.php';

$filter = getString('filter');
if (!in_array($filter, ['upcoming', 'completed'])) {
    $filter = 'all';
}

$where = "";
$params = [];
if ($filter !== 'all') {
    $where = "WHERE status = ?";
    $params[] = $filter;
}

try {
    $events = Database::fetchAll("SELECT * FROM events {$where} ORDER BY event_date DESC, id DESC", $params);
} catch (Exception $e) {
    $events = [];
}

$pageTitle = 'Events & News - Sirajganj Road Chowrasta High School';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #123B2A, #198754);">
    <div class="container text-center py-4">
        <h1 class="display-5 fw-bold mb-2" style="font-family: 'Hind Siliguri', sans-serif;">Events & News</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center mb-0">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-warning text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">Events</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Content -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <!-- Filter Tabs -->
        <div class="d-flex justify-content-center mb-5">
            <div class="btn-group p-1 rounded-pill bg-light border shadow-sm">
                <a href="<?= url('events.php') ?>" class="btn rounded-pill px-4 <?= $filter === 'all' ? 'btn-success text-white fw-bold' : 'btn-light text-muted' ?>">
                    All Activities
                </a>
                <a href="<?= url('events.php?filter=upcoming') ?>" class="btn rounded-pill px-4 <?= $filter === 'upcoming' ? 'btn-success text-white fw-bold' : 'btn-light text-muted' ?>">
                    Upcoming Events
                </a>
                <a href="<?= url('events.php?filter=completed') ?>" class="btn rounded-pill px-4 <?= $filter === 'completed' ? 'btn-success text-white fw-bold' : 'btn-light text-muted' ?>">
                    Completed Programs
                </a>
            </div>
        </div>

        <!-- Events List -->
        <div class="row g-4">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white hover-shadow transition">
                            <div class="position-relative" style="height: 200px; background-color: #123B2A;">
                                <img src="<?= asset($event['image'] ?: 'assets/images/events/default-event.jpg') ?>" 
                                     onerror="this.onerror=null;this.src='https://picsum.photos/seed/<?= urlencode($event['title']) ?>/600/400';" 
                                     alt="<?= e($event['title']) ?>" 
                                     class="w-100 h-100 object-fit-cover opacity-85">
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge <?= $event['status'] === 'upcoming' ? 'bg-success' : 'bg-secondary' ?> text-capitalize px-3 py-2 rounded-pill shadow-sm">
                                        <?= e($event['status']) ?>
                                    </span>
                                </div>
                                <div class="position-absolute bottom-0 start-0 m-3 p-2 px-3 rounded-3 bg-dark bg-opacity-75 text-white d-flex align-items-center gap-2">
                                    <i class="far fa-calendar-alt text-warning"></i>
                                    <small class="fw-bold"><?= formatDate($event['event_date'], 'M d, Y') ?></small>
                                </div>
                            </div>

                            <div class="card-body p-4 d-flex flex-column">
                                <h5 class="fw-bold text-dark mb-2"><?= e($event['title']) ?></h5>
                                <p class="text-muted small leading-relaxed mb-3">
                                    <?= e($event['description']) ?>
                                </p>
                                <div class="mt-auto pt-3 border-top small text-muted">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-map-marker-alt text-danger me-1"></i> <?= e($event['location']) ?></span>
                                        <?php if (!empty($event['event_time'])): ?>
                                            <span><i class="far fa-clock text-primary me-1"></i> <?= formatTime($event['event_time']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted fs-3 mb-2"><i class="far fa-calendar-times"></i></div>
                    <p class="text-muted">No events found matching your filter selection.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
