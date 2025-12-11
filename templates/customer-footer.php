            </main>

            <!-- Customer Portal Footer -->
            <footer class="customer-portal-footer">
                <div class="customer-portal-footer-content">
                    <?php
                    // Load template helpers for get_base_url() and get_brand_name()
                    if (!function_exists('get_base_url')) {
                        require_once __DIR__ . '/../includes/template_helpers.php';
                    }
                    $base_url = get_base_url();
                    ?>
                    <p class="customer-portal-footer-text">
                        &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_brand_name()); ?>. All rights reserved.
                    </p>
                    <div class="customer-portal-footer-links">
                        <a href="<?php echo $base_url; ?>/support.php" class="customer-portal-footer-link">Help Center</a>
                        <a href="<?php echo $base_url; ?>/privacy.php" class="customer-portal-footer-link">Privacy</a>
                        <a href="<?php echo $base_url; ?>/terms.php" class="customer-portal-footer-link">Terms</a>
                    </div>
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
    <script src="<?php echo js_url('customer-portal.js'); ?>"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo htmlspecialchars($js_file); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
