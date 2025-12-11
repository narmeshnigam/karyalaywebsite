<?php

/**
 * SellerPortal System
 * Home Page
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

// Load hero slides
use Karyalay\Models\HeroSlide;
use Karyalay\Models\WhyChooseCard;
use Karyalay\Models\Solution;

$heroSlideModel = new HeroSlide();
$heroSlides = $heroSlideModel->getPublishedSlides();

// Load why choose cards
$whyChooseModel = new WhyChooseCard();
$whyChooseCards = $whyChooseModel->getPublishedCards(6);

// Load featured solutions
$solutionModel = new Solution();
$featuredSolutions = $solutionModel->getFeaturedSolutions(6);

// Load testimonials
use Karyalay\Models\Testimonial;
$testimonialModel = new Testimonial();
$testimonials = $testimonialModel->getFeatured(6);

// Load featured case studies
use Karyalay\Models\CaseStudy;
$caseStudyModel = new CaseStudy();
$featuredCaseStudies = $caseStudyModel->getFeatured(3);

// Load featured blog posts
use Karyalay\Models\BlogPost;
$blogPostModel = new BlogPost();
$featuredBlogPosts = $blogPostModel->getFeatured(3);

// Set page variables
$page_title = 'Home';
$page_description = get_brand_name() . ' - ' . get_footer_company_description();

// Include header
include_header($page_title, $page_description);
?>

<!-- Hero Slider Section -->
<section class="hero-slider" aria-label="Hero Slider">
    <div class="hero-slider-container">
        <?php if (!empty($heroSlides)): ?>
            <?php foreach ($heroSlides as $index => $slide): ?>
                <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" 
                     style="background-image: url('<?php echo htmlspecialchars($slide['image_url']); ?>')"
                     data-link="<?php echo htmlspecialchars($slide['link_url'] ?? ''); ?>">
                    <div class="hero-slide-content">
                        <div class="container">
                            <?php if (!empty($slide['title'])): ?>
                                <h1 class="hero-title"><?php echo htmlspecialchars($slide['title']); ?></h1>
                            <?php endif; ?>
                            <?php if (!empty($slide['subtitle'])): ?>
                                <p class="hero-subtitle"><?php echo htmlspecialchars($slide['subtitle']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Default slide when no slides are configured -->
            <div class="hero-slide active" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="hero-slide-content">
                    <div class="container">
                        <h1 class="hero-title">Transform Your Business Operations</h1>
                        <p class="hero-subtitle"><?php echo htmlspecialchars(get_footer_company_description()); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Fixed CTA Buttons -->
        <div class="hero-actions-fixed">
            <div class="container">
                <div class="hero-actions">
                    <a href="<?php echo get_base_url(); ?>/register.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="<?php echo get_base_url(); ?>/demo.php" class="btn btn-outline btn-lg">Request Demo</a>
                </div>
            </div>
        </div>
        
        <?php if (count($heroSlides) > 1): ?>
            <!-- Slider Navigation -->
            <div class="hero-slider-nav">
                <button class="hero-slider-prev" aria-label="Previous slide">‹</button>
                <div class="hero-slider-dots">
                    <?php foreach ($heroSlides as $index => $slide): ?>
                        <button class="hero-slider-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                data-index="<?php echo $index; ?>"
                                aria-label="Go to slide <?php echo $index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <button class="hero-slider-next" aria-label="Next slide">›</button>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.hero-slider-dot');
    const prevBtn = document.querySelector('.hero-slider-prev');
    const nextBtn = document.querySelector('.hero-slider-next');
    
    if (slides.length <= 1) return;
    
    let currentIndex = 0;
    let autoSlideInterval;
    
    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
        currentIndex = index;
    }
    
    function nextSlide() {
        showSlide((currentIndex + 1) % slides.length);
    }
    
    function prevSlide() {
        showSlide((currentIndex - 1 + slides.length) % slides.length);
    }
    
    function startAutoSlide() {
        autoSlideInterval = setInterval(nextSlide, 5000);
    }
    
    function stopAutoSlide() {
        clearInterval(autoSlideInterval);
    }
    
    if (prevBtn) prevBtn.addEventListener('click', () => { stopAutoSlide(); prevSlide(); startAutoSlide(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { stopAutoSlide(); nextSlide(); startAutoSlide(); });
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => { stopAutoSlide(); showSlide(index); startAutoSlide(); });
    });
    
    // Click on slide to navigate to link
    slides.forEach(slide => {
        slide.addEventListener('click', function(e) {
            if (e.target.closest('.hero-actions') || e.target.closest('.hero-slider-nav')) return;
            const link = this.dataset.link;
            if (link) window.location.href = link;
        });
    });
    
    startAutoSlide();
});
</script>

<!-- Why Choose Section -->
<section class="section why-choose-section bg-gray-50">
    <div class="container">
        <h2 class="section-title">Why Choose Karyalay?</h2>
        <p class="section-subtitle">
            Everything you need to manage your business efficiently in one powerful platform
        </p>
        
        <div class="why-choose-grid">
            <?php if (!empty($whyChooseCards)): ?>
                <?php foreach ($whyChooseCards as $card): ?>
                    <div class="why-choose-card<?php echo !empty($card['link_url']) ? ' clickable' : ''; ?>"
                         <?php if (!empty($card['link_url'])): ?>onclick="window.location.href='<?php echo htmlspecialchars($card['link_url']); ?>'"<?php endif; ?>>
                        <div class="why-choose-card-image">
                            <img src="<?php echo htmlspecialchars($card['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($card['title']); ?>"
                                 loading="lazy">
                        </div>
                        <div class="why-choose-card-content">
                            <h3 class="why-choose-card-title"><?php echo htmlspecialchars($card['title']); ?></h3>
                            <?php if (!empty($card['description'])): ?>
                                <p class="why-choose-card-description"><?php echo htmlspecialchars($card['description']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default cards when none configured -->
                <div class="why-choose-card">
                    <div class="why-choose-card-image">
                        <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=640&h=360&fit=crop" alt="Modular Design" loading="lazy">
                    </div>
                    <div class="why-choose-card-content">
                        <h3 class="why-choose-card-title">Modular Design</h3>
                        <p class="why-choose-card-description">Choose the modules you need and scale as your business grows.</p>
                    </div>
                </div>
                <div class="why-choose-card">
                    <div class="why-choose-card-image">
                        <img src="https://images.unsplash.com/photo-1551434678-e076c223a692?w=640&h=360&fit=crop" alt="Easy to Use" loading="lazy">
                    </div>
                    <div class="why-choose-card-content">
                        <h3 class="why-choose-card-title">Easy to Use</h3>
                        <p class="why-choose-card-description">Intuitive interface designed for users of all technical levels.</p>
                    </div>
                </div>
                <div class="why-choose-card">
                    <div class="why-choose-card-image">
                        <img src="https://images.unsplash.com/photo-1563986768609-322da13575f3?w=640&h=360&fit=crop" alt="Secure & Reliable" loading="lazy">
                    </div>
                    <div class="why-choose-card-content">
                        <h3 class="why-choose-card-title">Secure & Reliable</h3>
                        <p class="why-choose-card-description">Enterprise-grade security with regular backups.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Powerful Solutions Section -->
<section class="section solutions-section">
    <div class="container">
        <h2 class="section-title">Powerful Solutions</h2>
        <p class="section-subtitle">
            Explore our comprehensive suite of business management solutions
        </p>
        
        <?php if (!empty($featuredSolutions)): ?>
            <div class="solutions-grid">
                <?php foreach ($featuredSolutions as $solution): ?>
                    <div class="solution-card">
                        <div class="solution-card-icon">
                            <?php if (!empty($solution['icon_image'])): ?>
                                <img src="<?php echo htmlspecialchars($solution['icon_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($solution['name']); ?>" 
                                     loading="lazy">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <h3 class="solution-card-title"><?php echo htmlspecialchars($solution['name']); ?></h3>
                        <?php if (!empty($solution['description'])): ?>
                            <p class="solution-card-description">
                                <?php echo htmlspecialchars(substr($solution['description'], 0, 120)); ?>
                                <?php echo strlen($solution['description']) > 120 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        <a href="<?php echo get_base_url(); ?>/solution/<?php echo urlencode($solution['slug']); ?>" class="btn btn-outline btn-sm">Learn More</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Default solutions when none featured -->
            <div class="solutions-grid">
                <div class="solution-card">
                    <div class="solution-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="solution-card-title">Customer Management</h3>
                    <p class="solution-card-description">
                        Manage customer relationships, track interactions, and provide excellent service.
                    </p>
                    <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-outline btn-sm">Learn More</a>
                </div>
                
                <div class="solution-card">
                    <div class="solution-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                    </div>
                    <h3 class="solution-card-title">Subscription Management</h3>
                    <p class="solution-card-description">
                        Handle subscriptions, billing, and renewals automatically with ease.
                    </p>
                    <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-outline btn-sm">Learn More</a>
                </div>
                
                <div class="solution-card">
                    <div class="solution-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="solution-card-title">Support Ticketing</h3>
                    <p class="solution-card-description">
                        Provide exceptional customer support with our integrated ticketing system.
                    </p>
                    <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-outline btn-sm">Learn More</a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-8">
            <a href="<?php echo get_base_url(); ?>/solutions.php" class="btn btn-primary">View All Solutions</a>
        </div>
    </div>
</section>

<!-- Case Studies Section -->
<?php if (!empty($featuredCaseStudies)): ?>
<section class="section case-studies-section">
    <div class="container">
        <h2 class="section-title">Success Stories</h2>
        <p class="section-subtitle">
            See how businesses like yours are achieving remarkable results with Karyalay
        </p>
        
        <div class="case-studies-grid">
            <?php foreach ($featuredCaseStudies as $caseStudy): ?>
                <article class="case-study-card">
                    <?php if (!empty($caseStudy['cover_image'])): ?>
                        <div class="case-study-card-image">
                            <img src="<?php echo htmlspecialchars($caseStudy['cover_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($caseStudy['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="case-study-card-content">
                        <div class="case-study-card-meta">
                            <span class="case-study-card-client"><?php echo htmlspecialchars($caseStudy['client_name']); ?></span>
                            <?php if (!empty($caseStudy['industry'])): ?>
                                <span class="case-study-card-industry"><?php echo htmlspecialchars($caseStudy['industry']); ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="case-study-card-title"><?php echo htmlspecialchars($caseStudy['title']); ?></h3>
                        <?php if (!empty($caseStudy['challenge'])): ?>
                            <div class="case-study-card-challenge">
                                <strong class="case-study-card-label">Challenge:</strong>
                                <p><?php echo htmlspecialchars(substr($caseStudy['challenge'], 0, 150)); ?>
                                <?php echo strlen($caseStudy['challenge']) > 150 ? '...' : ''; ?></p>
                            </div>
                        <?php endif; ?>
                        <a href="<?php echo get_base_url(); ?>/case-study/<?php echo urlencode($caseStudy['slug']); ?>" 
                           class="btn btn-outline btn-sm">Read Full Story →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="<?php echo get_base_url(); ?>/case-studies.php" class="btn btn-primary">View All Case Studies</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Blog Posts Section -->
<?php if (!empty($featuredBlogPosts)): ?>
<section class="section blog-posts-section">
    <div class="container">
        <h2 class="section-title">Latest from Our Blog</h2>
        <p class="section-subtitle">
            Stay updated with the latest insights, tips, and news from our team
        </p>
        
        <div class="blog-posts-grid">
            <?php foreach ($featuredBlogPosts as $post): ?>
                <article class="blog-post-card">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="blog-post-card-image">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="blog-post-card-content">
                        <div class="blog-post-card-meta">
                            <?php if (!empty($post['published_at'])): ?>
                                <span class="blog-post-card-date">
                                    <?php echo date('M j, Y', strtotime($post['published_at'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($post['tags']) && is_array($post['tags']) && count($post['tags']) > 0): ?>
                                <span class="blog-post-card-tag"><?php echo htmlspecialchars($post['tags'][0]); ?></span>
                            <?php endif; ?>
                        </div>
                        <h3 class="blog-post-card-title">
                            <a href="<?php echo get_base_url(); ?>/blog/<?php echo urlencode($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="blog-post-card-excerpt">
                                <?php echo htmlspecialchars(substr($post['excerpt'], 0, 120)); ?>
                                <?php echo strlen($post['excerpt']) > 120 ? '...' : ''; ?>
                            </p>
                        <?php endif; ?>
                        <a href="<?php echo get_base_url(); ?>/blog/<?php echo urlencode($post['slug']); ?>" 
                           class="blog-post-card-link">Read More →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="<?php echo get_base_url(); ?>/blog.php" class="btn btn-primary">View All Posts</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials Section -->
<?php if (!empty($testimonials)): ?>
<section class="section testimonials-section">
    <div class="container">
        <h2 class="section-title">What Our Customers Say</h2>
        <p class="section-subtitle">
            Don't just take our word for it - hear from businesses that trust Karyalay
        </p>
        
        <div class="testimonials-grid">
            <?php foreach ($testimonials as $testimonial): ?>
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="testimonial-star <?php echo $i <= $testimonial['rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <blockquote class="testimonial-text">
                        "<?php echo htmlspecialchars($testimonial['testimonial_text']); ?>"
                    </blockquote>
                    <div class="testimonial-author">
                        <?php if (!empty($testimonial['customer_image'])): ?>
                            <img src="<?php echo htmlspecialchars($testimonial['customer_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($testimonial['customer_name']); ?>"
                                 class="testimonial-avatar"
                                 loading="lazy">
                        <?php endif; ?>
                        <div class="testimonial-author-info">
                            <div class="testimonial-author-name"><?php echo htmlspecialchars($testimonial['customer_name']); ?></div>
                            <?php if (!empty($testimonial['customer_title']) || !empty($testimonial['customer_company'])): ?>
                                <div class="testimonial-author-title">
                                    <?php 
                                    $title_parts = array_filter([
                                        $testimonial['customer_title'] ?? '',
                                        $testimonial['customer_company'] ?? ''
                                    ]);
                                    echo htmlspecialchars(implode(' at ', $title_parts));
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<?php
$cta_title = "Ready to Transform Your Business?";
$cta_subtitle = "Get in touch with us today and discover how Karyalay can streamline your operations";
$cta_source = "homepage";
include __DIR__ . '/../templates/cta-form.php';
?>

<?php
// Include footer
include_footer();
?>
