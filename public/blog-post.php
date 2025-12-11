<?php

/**
 * SellerPortal System
 * Blog Post Detail Page
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/app.php';

// Set error reporting based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Load authentication helpers
require_once __DIR__ . '/../includes/auth_helpers.php';

// Start secure session
startSecureSession();

// Include template helpers
require_once __DIR__ . '/../includes/template_helpers.php';

// Database connection
use Karyalay\Models\BlogPost;
use Karyalay\Models\User;

// Get slug from query parameter
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: ' . get_base_url() . '/blog.php');
    exit;
}

try {
    $blogPostModel = new BlogPost();
    $post = $blogPostModel->findBySlug($slug);
    
    if (!$post || $post['status'] !== 'PUBLISHED') {
        // Blog post not found or not published
        header('HTTP/1.0 404 Not Found');
        $page_title = 'Post Not Found';
        $page_description = 'The requested blog post could not be found';
        include_header($page_title, $page_description);
        ?>
        <section class="section">
            <div class="container">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="text-3xl font-bold mb-4">Post Not Found</h1>
                        <p class="text-gray-600 mb-6">The blog post you're looking for doesn't exist or is no longer available.</p>
                        <a href="<?php echo get_base_url(); ?>/blog.php" class="btn btn-primary">View All Posts</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        include_footer();
        exit;
    }
    
    // Fetch author information if available
    $author = null;
    if (!empty($post['author_id'])) {
        $userModel = new User();
        $author = $userModel->findById($post['author_id']);
    }
    
} catch (Exception $e) {
    error_log('Error fetching blog post: ' . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    $page_title = 'Error';
    $page_description = 'An error occurred';
    include_header($page_title, $page_description);
    ?>
    <section class="section">
        <div class="container">
            <div class="card">
                <div class="card-body text-center">
                    <h1 class="text-3xl font-bold mb-4">Error</h1>
                    <p class="text-gray-600 mb-6">An error occurred while loading the blog post. Please try again later.</p>
                    <a href="<?php echo get_base_url(); ?>/blog.php" class="btn btn-primary">View All Posts</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    include_footer();
    exit;
}

// Set page variables
$page_title = htmlspecialchars($post['title']);
$page_description = htmlspecialchars($post['excerpt']);

// Include header
include_header($page_title, $page_description);
?>

<!-- Blog Post Header -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="max-w-4xl mx-auto">
            <?php if (!empty($post['tags']) && is_array($post['tags'])): ?>
                <div class="mb-4 flex flex-wrap gap-2">
                    <?php foreach ($post['tags'] as $tag): ?>
                        <span class="inline-block px-3 py-1 text-sm font-semibold text-primary bg-primary-light rounded-full">
                            <?php echo htmlspecialchars($tag); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <h1 class="text-4xl font-bold mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
            
            <div class="flex items-center gap-4 text-gray-600">
                <?php if ($author): ?>
                    <span>By <?php echo htmlspecialchars($author['name']); ?></span>
                <?php endif; ?>
                <?php if (!empty($post['published_at'])): ?>
                    <span>•</span>
                    <span><?php echo date('F j, Y', strtotime($post['published_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Blog Post Content -->
<section class="section">
    <div class="container">
        <div class="max-w-4xl mx-auto">
            <article class="prose prose-lg max-w-none">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </article>
        </div>
    </div>
</section>

<!-- Back to Blog -->
<section class="section bg-gray-50">
    <div class="container">
        <div class="max-w-4xl mx-auto text-center">
            <a href="<?php echo get_base_url(); ?>/blog.php" class="btn btn-outline">
                ← Back to Blog
            </a>
        </div>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
