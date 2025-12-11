        </main>

        <?php 
        $base_url = get_base_url(); 
        $app_base_url = get_app_base_url();
        ?>
        <footer class="site-footer" role="contentinfo">
            <div class="footer-container">
                <div class="footer-content">
                    <!-- Company Info -->
                    <div class="footer-section">
                        <div class="footer-logo"><?php echo render_brand_logo('dark_bg', 'footer-logo-img', 40); ?></div>
                        <p class="footer-description">
                            <?php echo htmlspecialchars(get_footer_company_description()); ?>
                        </p>
                    </div>

                    <!-- Product Links -->
                    <div class="footer-section">
                        <h3 class="footer-title">Product</h3>
                        <ul class="footer-links">
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/solutions.php" class="footer-link">Solutions</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/features.php" class="footer-link">Features</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/pricing.php" class="footer-link">Pricing</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/case-studies.php" class="footer-link">Case Studies</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Company Links -->
                    <div class="footer-section">
                        <h3 class="footer-title">Company</h3>
                        <ul class="footer-links">
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/about.php" class="footer-link">About Us</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/blog.php" class="footer-link">Blog</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/contact.php" class="footer-link">Contact</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/demo.php" class="footer-link">Request Demo</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Support Links -->
                    <div class="footer-section">
                        <h3 class="footer-title">Support</h3>
                        <ul class="footer-links">
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/support.php" class="footer-link">Help Center</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $app_base_url; ?>/app/support/tickets.php" class="footer-link">Support Tickets</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/terms.php" class="footer-link">Terms of Service</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/privacy.php" class="footer-link">Privacy Policy</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="<?php echo $base_url; ?>/refund.php" class="footer-link">Refund Policy</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p class="footer-copyright">
                        <?php echo get_footer_copyright_line(); ?>
                    </p>
                    
                    <ul class="footer-social">
                        <li>
                            <a href="#" class="footer-social-link" aria-label="Facebook">
                                <span>Facebook</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="footer-social-link" aria-label="Twitter">
                                <span>Twitter</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="footer-social-link" aria-label="LinkedIn">
                                <span>LinkedIn</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo js_url('navigation.js'); ?>"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo htmlspecialchars($js_file); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
