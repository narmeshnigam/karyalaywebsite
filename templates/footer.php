        </main>

        <footer class="site-footer" role="contentinfo">
            <div class="footer-container">
                <div class="footer-content">
                    <!-- Company Info -->
                    <div class="footer-section">
                        <div class="footer-logo">Karyalay</div>
                        <p class="footer-description">
                            Comprehensive business management system designed to streamline your operations and boost productivity.
                        </p>
                    </div>

                    <!-- Product Links -->
                    <div class="footer-section">
                        <h3 class="footer-title">Product</h3>
                        <ul class="footer-links">
                            <li class="footer-link-item">
                                <a href="/karyalayportal/solutions.php" class="footer-link">Solutions</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/features.php" class="footer-link">Features</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/pricing.php" class="footer-link">Pricing</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/case-studies.php" class="footer-link">Case Studies</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Company Links -->
                    <div class="footer-section">
                        <h3 class="footer-title">Company</h3>
                        <ul class="footer-links">
                            <li class="footer-link-item">
                                <a href="/karyalayportal/about.php" class="footer-link">About Us</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/blog.php" class="footer-link">Blog</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/contact.php" class="footer-link">Contact</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/demo.php" class="footer-link">Request Demo</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Support Links -->
                    <div class="footer-section">
                        <h3 class="footer-title">Support</h3>
                        <ul class="footer-links">
                            <li class="footer-link-item">
                                <a href="/karyalayportal/support.php" class="footer-link">Help Center</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/app/support/tickets.php" class="footer-link">Support Tickets</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/terms.php" class="footer-link">Terms of Service</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/privacy.php" class="footer-link">Privacy Policy</a>
                            </li>
                            <li class="footer-link-item">
                                <a href="/karyalayportal/refund.php" class="footer-link">Refund Policy</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="footer-bottom">
                    <p class="footer-copyright">
                        &copy; <?php echo date('Y'); ?> Karyalay. All rights reserved.
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
