<?php
/**
 * Solution Detail Page - Modern ERP Office Management
 * A beautifully designed page showcasing solution details with linked features
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../includes/auth_helpers.php';
startSecureSession();
require_once __DIR__ . '/../includes/template_helpers.php';

use Karyalay\Models\Solution;

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . get_base_url() . '/solutions.php');
    exit;
}

try {
    $solutionModel = new Solution();
    $solution = $solutionModel->findBySlug($slug);
    
    if (!$solution || $solution['status'] !== 'PUBLISHED') {
        header('HTTP/1.0 404 Not Found');
        $page_title = 'Solution Not Found';
        $page_description = 'The requested solution could not be found';
        include_header($page_title, $page_description);
        ?>
        <section class="section">
            <div class="container">
                <div class="not-found-card">
                    <div class="not-found-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h1>Solution Not Found</h1>
                    <p>The solution you're looking for doesn't exist or is no longer available.</p>
                    <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-primary">View All Solutions</a>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }
