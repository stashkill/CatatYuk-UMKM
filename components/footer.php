    </main>

    <?php if (isLoggedIn()): ?>
    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?>
                    </p>
                    <small class="text-muted">Aplikasi Pencatatan Keuangan UMKM By Keong Balap Dev.</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Login sebagai: <strong><?php echo getCurrentUser()['full_name']; ?></strong>
                        (<?php echo ucfirst(getCurrentUser()['role']); ?>)
                    </small>
                </div>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
    
    <!-- Page specific scripts -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo APP_URL; ?>/assets/js/<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Global JavaScript variables
        const APP_URL = '<?php echo APP_URL; ?>';
        const CURRENT_USER = <?php echo json_encode(getCurrentUser()); ?>;
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);

        // Load notifications on page load
        if (typeof loadNotifications === 'function') {
            loadNotifications();
        }
    </script>
</body>
</html>

