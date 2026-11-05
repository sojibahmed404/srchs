    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 px-4 text-center text-muted small no-print">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <span>&copy; <?= date('Y') ?> <?= e($schoolName) ?>. Integrated SMIS Platform.</span>
            <span>EIIN: 128456 | Rajshahi Education Board</span>
        </div>
    </footer>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile Sidebar Toggle
    const toggleBtn = document.getElementById('toggleSidebarBtn');
    const sidebar = document.getElementById('headSidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
</script>
</body>
</html>

