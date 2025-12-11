<?php

/**
 * SellerPortal System
 * Blog Index Page
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

try {
    $blogPostModel = new BlogPost();
    
    // Fetch all published blog posts
    $blogPosts = $blogPostModel->findAll(['status' => 'PUBLISHED']);
    
} catch (Exception $e) {
    error_log('Error fetching blog posts: ' . $e->getMessage());
    $blogPosts = [];
}

// Set page variables
$page_title = 'Blog';
$page_description = 'Insights, tips, and updates from the Karyalay team';

// Include header
include_header($page_title, $page_description);
?>

<!-- Page Header -->
<section class="section bg-gray-50">
    <div class="container">
        <h1 class="text-4xl font-bold mb-4">Blog</h1>
        <p class="text-xl text-gray-600">
            Insights, tips, and updates to help you get the most out of Karyalay
        </p>
    </div>
</section>

<!-- Blog Posts List -->
<section class="section">
    <div class="container">
        <?php if (empty($blogPosts)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-gray-600">No blog posts available at the moment. Check back soon for new content!</p>
                </div>
            </div>
        <?php else: ?>
            <div class="max-w-4xl mx-auto space-y-8">
                <?php foreach ($blogPosts as $post): ?>
                    <article class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <?php if (!empty($post['tags']) && is_array($post['tags'])): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach (array_slice($post['tags'], 0, 3) as $tag): ?>
                                            <span class="inline-block px-2 py-1 text-xs font-semibold text-primary bg-primary-light rounded">
                                                <?php echo htmlspecialchars($tag); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h2 class="text-2xl font-bold mb-3">
                                <a href="/blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                   class="text-gray-900 hover:text-primary">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            
                            <div class="text-sm text-gray-500 mb-4">
                                <?php if (!empty($post['published_at'])): ?>
                                    Published on <?php echo date('F j, Y', strtotime($post['published_at'])); ?>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-gray-700 mb-4">
                                <?php echo htmlspecialchars($post['excerpt']); ?>
                            </p>
                            
                            <a href="/blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                               class="btn btn-outline btn-sm">
                                Read More
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
// Include footer
include_footer();
?>
