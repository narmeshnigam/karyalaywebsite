            </main>

            <!-- Admin Footer -->
            <footer class="admin-footer">
                <div class="admin-footer-content">
                    <p class="admin-footer-text">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_brand_name()); ?> Admin Panel. All rights reserved.
                    </p>
                    <p class="admin-footer-version">
                        Version 1.0.0
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <!-- JavaScript -->
    <?php
    // Load template helpers for js_url()
    if (!function_exists('js_url')) {
        require_once __DIR__ . '/../includes/template_helpers.php';
    }
    ?>
    <script src="<?php echo js_url('navigation.js'); ?>"></script>
    <script src="<?php echo js_url('admin.js'); ?>"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo htmlspecialchars($js_file); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
